#/bin/bash

# aws ecs execute-command \
#     --region us-east-1 \
#     --cluster dev \
#     --task 377ad02f4c0047efbdb288391ce6facd \
#     --container drupal-service \
#     --command "/bin/bash" \
#     --interactive

if [ $# -ne 3 ]; then
  echo "Wrong number of arguments"
  exit 1
fi

GROUP="$1"
WHICH="$2"
COMMAND="${@:3}"

PARAMS='{"commands":["export HOME=/root;'$COMMAND'"],"executionTimeout":["600"]}'

if [[ $WHICH == "all" ]]; then
  INSTANCES=$(aws autoscaling describe-auto-scaling-groups \
    --auto-scaling-group-names $GROUP \
    --query 'AutoScalingGroups[0].Instances[?HealthStatus==`Healthy` && LifecycleState==`InService`].InstanceId' \
    --output text | tr '\t' '\n'
  )
else
  INSTANCES=$(aws autoscaling describe-auto-scaling-groups \
    --auto-scaling-group-names $GROUP \
    --query 'AutoScalingGroups[0].Instances[?HealthStatus==`Healthy` && LifecycleState==`InService`].InstanceId' \
    --output text | tr '\t' '\n' | shuf -n$WHICH
  )
fi

if [[ $INSTANCES == "None" ]]; then
  echo 'No available instances associated with this AutoScale Group or AutoScale group not found'
  exit 1
fi

ID=$( aws ssm send-command \
  --instance-ids $INSTANCES \
  --document-name "AWS-RunShellScript" \
  --comment "Custom command execution" \
  --parameters "$PARAMS" \
  --timeout-seconds 600 \
  --query Command.CommandId \
  --output text
)

n_instances=$( echo $INSTANCES | wc -w )
while true; do
    finished=0
    for instance in $INSTANCES; do
        STATUS=$( aws ssm get-command-invocation --command-id $ID --instance-id $instance --query Status --output text | tr '[A-Z]' '[a-z]' )
        NOW=$( date +%Y-%m-%dT%H:%M:%S%z )
        echo $NOW $instance: $STATUS
        case $STATUS in
            pending|inprogress|delayed) : ;;
            *) finished=$(( finished + 1 )) ;;
        esac
    done
    [ $finished -ge $n_instances ] && break
    sleep 2
done

for instance in $INSTANCES; do 
  STATUS=$( aws ssm get-command-invocation --command-id "$ID" --instance-id "$instance" --query Status --output text )
  OUT_RESULT=$( aws ssm get-command-invocation --command-id "$ID" --instance-id "$instance" --query StandardOutputContent --output text )
  ERR_RESULT=$( aws ssm get-command-invocation --command-id "$ID" --instance-id "$instance" --query StandardErrorContent --output text )

  echo "------------------------------------"
  echo "RESULTS FROM $instance (STATUS $STATUS):"
  if [ -n "$OUT_RESULT" ]; then
      echo "STDOUT:"
      echo "$OUT_RESULT"
      echo "------------------------------------"
  fi
  if [ -n "$ERR_RESULT" ]; then
      echo "STDERR:"
      echo "$ERR_RESULT"
      echo "------------------------------------"
  fi
  if [ -z "$OUT_RESULT" -a -z "$ERR_RESULT" ]; then
      echo NO OUTPUT RETURNED
  fi
done